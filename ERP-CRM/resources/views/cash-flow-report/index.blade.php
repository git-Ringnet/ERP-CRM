@extends('layouts.app')

@section('title', 'Báo cáo Dòng tiền')

@section('page-title', 'Báo cáo Dòng tiền')

@section('content')
<div class="container-fluid">
    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6 no-print">
        <form action="{{ route('cash-flow-report.index') }}" method="GET" class="flex flex-wrap items-center gap-4">
            <div>
                <label for="year" class="block text-xs font-semibold text-gray-500 uppercase mb-1">Năm báo cáo</label>
                <select name="year" id="year" class="form-select rounded-lg border-gray-300 text-sm focus:ring-primary focus:border-primary" onchange="this.form.submit()">
                    @for ($y = now()->year; $y >= now()->year - 5; $y--)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>
            <div class="flex items-end gap-2 ml-auto">
                <a href="{{ route('cash-flow-report.config') }}" class="bg-gray-800 hover:bg-gray-900 text-white px-4 py-2 rounded-lg text-sm font-medium transition-all flex items-center">
                    <i class="fas fa-cogs mr-2"></i> Cấu hình
                </a>
                <button type="button" onclick="window.print()" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium border border-gray-300 transition-all flex items-center">
                    <i class="fas fa-print mr-2"></i> In báo cáo
                </button>
                <button type="submit" class="bg-primary hover:bg-opacity-90 text-white px-4 py-2 rounded-lg text-sm font-medium transition-all flex items-center">
                    <i class="fas fa-sync-alt mr-2"></i> Làm mới
                </button>
                <a href="{{ route('cash-flow-report.export', ['year' => $year]) }}" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-all flex items-center">
                    <i class="fas fa-file-excel mr-2"></i> Xuất Excel
                </a>
            </div>
        </form>
    </div>

    <!-- Monthly Horizontal Report -->
    <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6 overflow-x-auto printable-area">
        <div class="text-center mb-6">
            <h1 class="text-xl font-bold uppercase text-orange-600">BÁO CÁO DÒNG TIỀN</h1>
            <h2 class="text-2xl font-bold text-orange-600">{{ $year }}</h2>
            <p class="text-sm italic text-gray-500">Người lập: {{ auth()->user()->name }} | Ngày lập: {{ now()->format('d/m/Y') }}</p>
        </div>

        <table class="w-full text-sm border-collapse border border-gray-300 min-w-[1800px]">
            <thead>
                <tr class="bg-orange-100 text-gray-700">
                    <th class="border border-gray-300 p-2 text-left w-80 sticky left-0 bg-orange-100 z-10">Nội dung</th>
                    @foreach($reportData['months'] as $month)
                        <th class="border border-gray-300 p-2 text-center">THÁNG {{ $month }}</th>
                    @endforeach
                    <th class="border border-gray-300 p-2 text-center bg-orange-200">TỔNG CỘNG</th>
                </tr>
            </thead>
            <tbody>
                <!-- Opening Balance -->
                <tr class="bg-yellow-50 font-bold">
                    <td class="border border-gray-300 p-2 sticky left-0 bg-yellow-50 z-10">Tiền mặt Hiện có (đầu tháng)</td>
                    @foreach($reportData['months'] as $month)
                        <td class="border border-gray-300 p-2 text-right">
                            {{ number_format($reportData['opening_balances'][$month]) }}
                        </td>
                    @endforeach
                    <td class="border border-gray-300 p-2 text-right bg-yellow-100">
                        {{ number_format($reportData['opening_balances'][1]) }}
                    </td>
                </tr>

                <!-- Income Section -->
                <tr class="bg-orange-500 text-white font-bold">
                    <td colspan="{{ count($reportData['months']) + 2 }}" class="p-2">Dòng tiền Thu vào</td>
                </tr>
                @foreach($reportData['income_items'] as $itemName)
                <tr>
                    <td class="border border-gray-300 p-2 pl-4 sticky left-0 bg-white z-10">{{ $itemName }}</td>
                    @php $itemTotal = 0; @endphp
                    @foreach($reportData['months'] as $month)
                        @php 
                            $amount = $reportData['income_data'][$itemName][$month] ?? 0;
                            $itemTotal += $amount;
                        @endphp
                        <td class="border border-gray-300 p-2 text-right {{ $amount > 0 ? 'text-black' : 'text-gray-300' }}">
                            {{ $amount > 0 ? number_format($amount) : '0' }}
                        </td>
                    @endforeach
                    <td class="border border-gray-300 p-2 text-right font-medium">
                        {{ $itemTotal > 0 ? number_format($itemTotal) : '0' }}
                    </td>
                </tr>
                @endforeach
                <tr class="bg-orange-100 font-bold">
                    <td class="border border-gray-300 p-2 sticky left-0 bg-orange-100 z-10">Tổng cộng Thu vào</td>
                    @php $yearTotalIncome = 0; @endphp
                    @foreach($reportData['months'] as $month)
                        @php $yearTotalIncome += $reportData['total_income'][$month]; @endphp
                        <td class="border border-gray-300 p-2 text-right">
                            {{ number_format($reportData['total_income'][$month]) }}
                        </td>
                    @endforeach
                    <td class="border border-gray-300 p-2 text-right">
                        {{ number_format($yearTotalIncome) }}
                    </td>
                </tr>

                <!-- Expense Section -->
                <tr class="bg-orange-400 text-white font-bold">
                    <td colspan="{{ count($reportData['months']) + 2 }}" class="p-2">Dòng tiền Chi ra</td>
                </tr>
                @foreach($reportData['expense_items'] as $itemName)
                <tr>
                    <td class="border border-gray-300 p-2 pl-4 sticky left-0 bg-white z-10">{{ $itemName }}</td>
                    @php $itemTotal = 0; @endphp
                    @foreach($reportData['months'] as $month)
                        @php 
                            $amount = $reportData['expense_data'][$itemName][$month] ?? 0;
                            $itemTotal += $amount;
                        @endphp
                        <td class="border border-gray-300 p-2 text-right {{ $amount > 0 ? 'text-black' : 'text-gray-300' }}">
                            {{ $amount > 0 ? number_format($amount) : '0' }}
                        </td>
                    @endforeach
                    <td class="border border-gray-300 p-2 text-right font-medium">
                        {{ $itemTotal > 0 ? number_format($itemTotal) : '0' }}
                    </td>
                </tr>
                @endforeach
                <tr class="bg-orange-100 font-bold">
                    <td class="border border-gray-300 p-2 sticky left-0 bg-orange-100 z-10">Tổng cộng Chi ra</td>
                    @php $yearTotalExpense = 0; @endphp
                    @foreach($reportData['months'] as $month)
                        @php $yearTotalExpense += $reportData['total_expense'][$month]; @endphp
                        <td class="border border-gray-300 p-2 text-right">
                            {{ number_format($reportData['total_expense'][$month]) }}
                        </td>
                    @endforeach
                    <td class="border border-gray-300 p-2 text-right">
                        {{ number_format($yearTotalExpense) }}
                    </td>
                </tr>

                <!-- Final Position -->
                <tr class="bg-blue-600 text-white font-bold">
                    <td class="border border-blue-700 p-2 sticky left-0 bg-blue-600 z-10">Tình hình Tiền mặt Hiện tại (Sau chi)</td>
                    @foreach($reportData['months'] as $month)
                        <td class="border border-blue-700 p-2 text-right">
                            {{ number_format($reportData['closing_balances'][$month]) }}
                        </td>
                    @endforeach
                    <td class="border border-blue-700 p-2 text-right bg-blue-800">
                        {{ number_format($reportData['closing_balances'][12]) }}
                    </td>
                </tr>
            </tbody>
        </table>


    </div>
</div>

<style>
    @media print {
        .no-print { display: none !important; }
        .printable-area { border: none !important; box-shadow: none !important; width: 100% !important; max-width: none !important; }
        @page { size: landscape; margin: 1cm; }
    }
    .sticky { position: sticky; left: 0; }
</style>
@endsection
