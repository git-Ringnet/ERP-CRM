@extends('layouts.app')

@section('title', 'Báo cáo Margin Misa')
@section('page-title', 'Báo cáo Lãi/Lỗ (Margin) theo đơn hàng')

@section('content')
<div class="space-y-4">
    <div class="flex items-center justify-between">
        <form method="GET" class="flex items-center gap-2">
            <input type="date" name="date_from" value="{{ $dateFrom }}" class="rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 text-sm">
            <span class="text-gray-500">đến</span>
            <input type="date" name="date_to" value="{{ $dateTo }}" class="rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 text-sm">
            <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-900 focus:outline-none focus:border-blue-900 focus:ring ring-blue-300 disabled:opacity-25 transition ease-in-out duration-150">
                <i class="fas fa-filter mr-2"></i> {{ __('Lọc') }}
            </button>
        </form>
        <div class="flex gap-2">
            <a href="{{ route('reports.export-misa-margin', ['date_from' => $dateFrom, 'date_to' => $dateTo]) }}" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 active:bg-green-800 focus:outline-none focus:border-green-800 focus:ring ring-green-300 disabled:opacity-25 transition ease-in-out duration-150">
                <i class="fas fa-file-excel mr-2"></i> {{ __('Xuất Excel') }}
            </a>
            <button onclick="window.print()" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:text-gray-500 focus:outline-none focus:border-blue-300 focus:ring ring-blue-200 disabled:opacity-25 transition ease-in-out duration-150">
                <i class="fas fa-print mr-2"></i> {{ __('In') }}
            </button>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-sm">
                <thead>
                    <tr class="bg-blue-800 text-white font-bold text-center">
                        <th class="border border-blue-900 px-2 py-3 w-12">STT</th>
                        <th class="border border-blue-900 px-3 py-3 w-64 text-left">Tên khách hàng</th>
                        <th class="border border-blue-900 px-3 py-3 w-40">Số hóa đơn / đơn hàng</th>
                        <th class="border border-blue-900 px-3 py-3 w-32">Ngày xuất</th>
                        <th class="border border-blue-900 px-3 py-3 w-32">HÃNG</th>
                        <th class="border border-blue-900 px-2 py-3 w-16">License</th>
                        <th class="border border-blue-900 px-3 py-3 w-48 text-left">Loại hàng</th>
                        <th class="border border-blue-900 px-3 py-3 w-40">Mã hàng chính</th>
                        <th class="border border-blue-900 px-3 py-3 w-32 bg-blue-100 text-blue-900">Margin</th>
                        <th class="border border-blue-900 px-3 py-3 w-24 bg-blue-100 text-blue-900">Margin %</th>
                        <th class="border border-blue-900 px-3 py-3 w-40 bg-blue-100 text-blue-900">NV Kinh doanh</th>
                        <th class="border border-blue-900 px-3 py-3 w-48 bg-green-100 text-green-900">Đã thanh toán</th>
                        <th class="border border-blue-900 px-3 py-3 w-24 bg-green-100 text-green-900">Tỷ lệ %</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($rows as $index => $row)
                        <tr class="hover:bg-gray-50">
                            <td class="border border-gray-300 px-2 py-2 text-center text-gray-500">{{ $index + 1 }}</td>
                            <td class="border border-gray-300 px-3 py-2 font-medium">{{ $row['customer_name'] }}</td>
                            <td class="border border-gray-300 px-3 py-2 text-center font-mono text-xs">{{ $row['sale_code'] }}</td>
                            <td class="border border-gray-300 px-3 py-2 text-center">{{ $row['sale_date']->format('d/m/Y') }}</td>
                            <td class="border border-gray-300 px-3 py-2 text-center font-bold">{{ $row['supplier'] }}</td>
                            <td class="border border-gray-300 px-2 py-2 text-center">
                                @if($row['is_license'])
                                    <span class="text-blue-600"><i class="fas fa-check"></i></span>
                                @endif
                            </td>
                            <td class="border border-gray-300 px-3 py-2 text-xs">{{ $row['item_type'] }}</td>
                            <td class="border border-gray-300 px-3 py-2 text-center font-mono text-xs">{{ $row['product_code'] }}</td>
                            <td class="border border-gray-300 px-3 py-2 text-right font-bold text-blue-700">
                                {{ number_format($row['net_profit'], 0, ',', '.') }}
                            </td>
                            <td class="border border-gray-300 px-3 py-2 text-center">
                                <span class="px-2 py-1 rounded text-xs font-bold {{ $row['margin_percent'] >= 20 ? 'bg-green-100 text-green-800' : ($row['margin_percent'] >= 10 ? 'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800') }}">
                                    {{ round($row['margin_percent'], 1) }}%
                                </span>
                            </td>
                            <td class="border border-gray-300 px-3 py-2 text-center text-xs">{{ $row['salesperson'] }}</td>
                            <td class="border border-gray-300 px-3 py-2 text-right font-medium text-green-700">
                                {{ $row['paid_amount'] > 0 ? number_format($row['paid_amount'], 0, ',', '.') : 'Chưa thanh toán' }}
                            </td>
                            <td class="border border-gray-300 px-3 py-2 text-center">
                                <div class="w-full bg-gray-200 rounded-full h-1.5 mb-1">
                                    <div class="bg-green-600 h-1.5 rounded-full" style="width: {{ min(100, $row['payment_percent']) }}%"></div>
                                </div>
                                <span class="text-[10px] font-bold">{{ round($row['payment_percent'], 0) }}%</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="13" class="px-6 py-10 text-center text-gray-500 italic">
                                Không có dữ liệu trong khoảng thời gian này.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
