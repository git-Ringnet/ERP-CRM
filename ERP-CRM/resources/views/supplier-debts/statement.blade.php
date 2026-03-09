@extends('layouts.app')

@section('title', 'Sao kê công nợ NCC - ' . $supplier->name)
@section('page-title', 'Sao kê công nợ NCC')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <div class="flex items-center text-sm text-gray-500 mb-2">
                    <a href="{{ route('supplier-debts.index') }}" class="hover:text-blue-600">Công nợ NCC</a>
                    <i class="fas fa-chevron-right mx-2 text-xs"></i>
                    <a href="{{ route('supplier-debts.show', $supplier) }}" class="hover:text-blue-600">{{ $supplier->name }}</a>
                    <i class="fas fa-chevron-right mx-2 text-xs"></i>
                    <span class="text-gray-800">Sao kê</span>
                </div>
                <h2 class="text-xl font-bold text-gray-800">
                    <i class="fas fa-file-alt text-indigo-600 mr-2"></i>Sao kê công nợ - {{ $supplier->name }}
                </h2>
            </div>
            <div class="flex gap-2">
                <button onclick="window.print()" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    <i class="fas fa-print mr-2"></i> In
                </button>
                <a href="{{ route('supplier-debts.export-statement', [$supplier, 'date_from' => $dateFrom, 'date_to' => $dateTo]) }}"
                    class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                    <i class="fas fa-file-csv mr-2"></i> Export CSV
                </a>
                <a href="{{ route('supplier-debts.show', $supplier) }}"
                    class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">
                    <i class="fas fa-arrow-left mr-2"></i> Quay lại
                </a>
            </div>
        </div>
        <form method="GET" class="mt-4 flex items-end gap-4 flex-wrap print:hidden">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Từ ngày</label>
                <input type="date" name="date_from" value="{{ $dateFrom }}" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Đến ngày</label>
                <input type="date" name="date_to" value="{{ $dateTo }}" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                <i class="fas fa-filter mr-2"></i> Lọc
            </button>
        </form>
    </div>

    <!-- Statement -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200" id="statementContent">
        <!-- Print Header -->
        <div class="p-6 border-b border-gray-200">
            <div class="text-center">
                <h2 class="text-2xl font-bold text-gray-800">SAO KÊ CÔNG NỢ NHÀ CUNG CẤP</h2>
                <p class="text-gray-600 mt-1">Kỳ: {{ \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($dateTo)->format('d/m/Y') }}</p>
            </div>
            <div class="grid grid-cols-2 gap-4 mt-4 text-sm">
                <div>
                    <p><strong>NCC:</strong> {{ $supplier->name }}</p>
                    <p><strong>Mã:</strong> {{ $supplier->code }}</p>
                    @if($supplier->tax_code)<p><strong>MST:</strong> {{ $supplier->tax_code }}</p>@endif
                </div>
                <div class="text-right">
                    <p><strong>Dư đầu kỳ:</strong> <span class="text-blue-600 font-medium">{{ number_format($openingBalance, 0, ',', '.') }} VND</span></p>
                    <p><strong>Dư cuối kỳ:</strong> <span class="text-red-600 font-bold">{{ number_format($closingBalance, 0, ',', '.') }} VND</span></p>
                </div>
            </div>
        </div>

        <!-- Transaction Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ngày</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Loại</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Chứng từ</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nội dung</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Phát sinh Nợ</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Phát sinh Có</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Lũy kế</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <!-- Opening Balance Row -->
                    <tr class="bg-blue-50">
                        <td class="px-4 py-3 text-sm" colspan="4"><strong>Dư đầu kỳ</strong></td>
                        <td class="px-4 py-3 text-sm text-right"></td>
                        <td class="px-4 py-3 text-sm text-right"></td>
                        <td class="px-4 py-3 text-sm text-right font-bold text-blue-600">{{ number_format($openingBalance, 0, ',', '.') }}</td>
                    </tr>
                    @foreach($transactions as $txn)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm text-gray-500">{{ \Carbon\Carbon::parse($txn['date'])->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-sm">
                            @if($txn['type'] === 'debit')
                                <span class="px-2 py-0.5 rounded text-xs bg-red-100 text-red-800">Mua hàng</span>
                            @else
                                <span class="px-2 py-0.5 rounded text-xs bg-green-100 text-green-800">Thanh toán</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm font-medium text-gray-800">{{ $txn['code'] }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $txn['description'] }}</td>
                        <td class="px-4 py-3 text-sm text-right {{ $txn['debit'] > 0 ? 'text-red-600' : '' }}">
                            {{ $txn['debit'] > 0 ? number_format($txn['debit'], 0, ',', '.') : '' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-right {{ $txn['credit'] > 0 ? 'text-green-600' : '' }}">
                            {{ $txn['credit'] > 0 ? number_format($txn['credit'], 0, ',', '.') : '' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-right font-medium">{{ number_format($txn['balance'], 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                    <!-- Closing Balance Row -->
                    <tr class="bg-red-50">
                        <td class="px-4 py-3 text-sm" colspan="4"><strong>Dư cuối kỳ</strong></td>
                        <td class="px-4 py-3 text-sm text-right"></td>
                        <td class="px-4 py-3 text-sm text-right"></td>
                        <td class="px-4 py-3 text-sm text-right font-bold text-red-600">{{ number_format($closingBalance, 0, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Footer -->
        <div class="p-6 border-t border-gray-200">
            <div class="grid grid-cols-2 gap-8 text-center text-sm">
                <div>
                    <p class="font-semibold text-gray-800">Bên mua</p>
                    <p class="text-gray-500 mt-4">(Ký, ghi rõ họ tên)</p>
                </div>
                <div>
                    <p class="font-semibold text-gray-800">Bên bán (NCC)</p>
                    <p class="text-gray-500 mt-4">(Ký, ghi rõ họ tên)</p>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
@media print {
    .print\:hidden { display: none !important; }
    body { background: white; }
    aside, header, footer { display: none !important; }
    main { padding: 0 !important; }
    .shadow, .shadow-sm { box-shadow: none !important; }
}
</style>
@endpush
@endsection
