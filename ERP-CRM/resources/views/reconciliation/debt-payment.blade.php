@extends('layouts.app')

@section('title', 'Đối soát Công nợ ↔ Thanh toán')
@section('page-title', 'Đối soát Công nợ ↔ Thanh toán')

@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <div class="flex items-center text-sm text-gray-500 mb-2">
                    <a href="{{ route('reconciliation.index') }}" class="hover:text-blue-600">Đối soát</a>
                    <i class="fas fa-chevron-right mx-2 text-xs"></i>
                    <span class="text-gray-800">Công nợ ↔ Thanh toán</span>
                </div>
                <h2 class="text-xl font-bold text-gray-800"><i class="fas fa-file-invoice-dollar text-emerald-600 mr-2"></i>Đối soát Công nợ ↔ Thanh toán</h2>
            </div>
            <a href="{{ route('reconciliation.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200"><i class="fas fa-arrow-left mr-2"></i> Quay lại</a>
        </div>
        <form method="GET" class="mt-4 flex items-end gap-4 flex-wrap">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Đối tượng</label>
                <select name="party" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="all" {{ ($filters['party'] ?? '') === 'all' ? 'selected' : '' }}>Tất cả</option>
                    <option value="customer" {{ ($filters['party'] ?? '') === 'customer' ? 'selected' : '' }}>Khách hàng</option>
                    <option value="supplier" {{ ($filters['party'] ?? '') === 'supplier' ? 'selected' : '' }}>Nhà cung cấp</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Từ ngày</label>
                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Đến ngày</label>
                <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <button type="submit" class="inline-flex items-center px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700"><i class="fas fa-filter mr-2"></i> Lọc</button>
            <a href="{{ route('reconciliation.debt-payment') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300"><i class="fas fa-redo mr-2"></i> Xóa lọc</a>
        </form>
    </div>

    @php $party = $filters['party'] ?? 'all'; @endphp

    {{-- ===================== CUSTOMER SECTION ===================== --}}
    @if($party === 'all' || $party === 'customer')
    <div class="border-l-4 border-blue-500 pl-2">
        <h3 class="text-lg font-bold text-gray-700"><i class="fas fa-users text-blue-500 mr-2"></i>Khách hàng</h3>
    </div>

    {{-- Customer Debt Mismatches --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200" x-data="{ showHelp: false }">
        <div class="p-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                <i class="fas fa-money-bill-wave text-red-500 mr-2"></i>Công nợ KH không khớp
                <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ count($results['debt_mismatches']) > 0 ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">{{ count($results['debt_mismatches']) }}</span>
                <i class="fas fa-info-circle text-blue-400 ml-2 cursor-pointer hover:text-blue-600 transition-colors" @click="showHelp = !showHelp"></i>
            </h3>
            <p class="text-sm text-gray-500 mt-1">debt_amount ≠ (total − tổng thanh toán)</p>
        </div>
        <div x-show="showHelp" x-transition class="p-4 bg-blue-50 border-b border-blue-100 text-sm text-blue-800">
            <p><strong>Hướng dẫn:</strong> Số tiền "Còn nợ" ghi nhận trên đơn hàng không khớp với phép tính lấy Tổng tiền trừ đi Tổng các lần thanh toán đã thực hiện.</p>
        </div>
        @if(count($results['debt_mismatches']) > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mã đơn</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">KH</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ngày</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Tổng</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Đã TT</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Nợ GN</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Nợ đúng</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Lệch</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($results['debt_mismatches'] as $item)
                    <tr class="hover:bg-red-50">
                        <td class="px-4 py-3 text-sm"><a href="{{ route('sales.show', $item['sale_id']) }}" class="text-blue-600 hover:underline font-medium">{{ $item['sale_code'] }}</a></td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $item['customer_name'] }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $item['date'] }}</td>
                        <td class="px-4 py-3 text-sm text-right">{{ number_format($item['total'], 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-sm text-right text-green-600">{{ number_format($item['total_paid'], 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-sm text-right text-orange-600">{{ number_format($item['recorded_debt'], 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-sm text-right text-blue-600">{{ number_format($item['expected_debt'], 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-sm text-right font-semibold text-red-600">{{ number_format($item['difference'], 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="p-8 text-center text-gray-500"><i class="fas fa-check-circle text-green-400 text-3xl mb-2"></i><p>Tất cả công nợ KH đều khớp</p></div>
        @endif
    </div>

    {{-- Customer Paid Amount Mismatches --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200" x-data="{ showHelp: false }">
        <div class="p-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                <i class="fas fa-coins text-yellow-500 mr-2"></i>Số tiền TT KH không khớp
                <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ count($results['paid_mismatches']) > 0 ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">{{ count($results['paid_mismatches']) }}</span>
                <i class="fas fa-info-circle text-blue-400 ml-2 cursor-pointer hover:text-blue-600 transition-colors" @click="showHelp = !showHelp"></i>
            </h3>
        </div>
        <div x-show="showHelp" x-transition class="p-4 bg-blue-50 border-b border-blue-100 text-sm text-blue-800">
            <p><strong>Hướng dẫn:</strong> Ô "Số tiền đã thanh toán" trên đơn hàng không khớp với tổng từ module "Lịch sử thanh toán".</p>
        </div>
        @if(count($results['paid_mismatches']) > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mã đơn</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">KH</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ngày</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Tổng</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">GN đã TT</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Thực TT</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Lệch</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($results['paid_mismatches'] as $item)
                    <tr class="hover:bg-yellow-50">
                        <td class="px-4 py-3 text-sm"><a href="{{ route('sales.show', $item['sale_id']) }}" class="text-blue-600 hover:underline font-medium">{{ $item['sale_code'] }}</a></td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $item['customer_name'] }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $item['date'] }}</td>
                        <td class="px-4 py-3 text-sm text-right">{{ number_format($item['total'], 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-sm text-right text-orange-600">{{ number_format($item['recorded_paid'], 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-sm text-right text-blue-600">{{ number_format($item['actual_paid'], 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-sm text-right font-semibold text-red-600">{{ number_format($item['difference'], 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="p-8 text-center text-gray-500"><i class="fas fa-check-circle text-green-400 text-3xl mb-2"></i><p>OK</p></div>
        @endif
    </div>

    {{-- Customer Status Mismatches --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200" x-data="{ showHelp: false }">
        <div class="p-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                <i class="fas fa-tag text-indigo-500 mr-2"></i>Trạng thái TT KH sai
                <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ count($results['status_mismatches']) > 0 ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">{{ count($results['status_mismatches']) }}</span>
                <i class="fas fa-info-circle text-blue-400 ml-2 cursor-pointer hover:text-blue-600 transition-colors" @click="showHelp = !showHelp"></i>
            </h3>
        </div>
        <div x-show="showHelp" x-transition class="p-4 bg-blue-50 border-b border-blue-100 text-sm text-blue-800">
            <p><strong>Hướng dẫn:</strong> Trạng thái thanh toán không khớp với số tiền thực tế đã thu.</p>
        </div>
        @if(count($results['status_mismatches']) > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mã đơn</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">KH</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Tổng</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Đã TT</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">TT hiện tại</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">TT đúng</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($results['status_mismatches'] as $item)
                    <tr class="hover:bg-indigo-50">
                        <td class="px-4 py-3 text-sm"><a href="{{ route('sales.show', $item['sale_id']) }}" class="text-blue-600 hover:underline font-medium">{{ $item['sale_code'] }}</a></td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $item['customer_name'] }}</td>
                        <td class="px-4 py-3 text-sm text-right">{{ number_format($item['total'], 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-sm text-right">{{ number_format($item['paid_amount'], 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-sm"><span class="px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">{{ $item['recorded_status_label'] }}</span></td>
                        <td class="px-4 py-3 text-sm"><span class="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">{{ $item['expected_status_label'] }}</span></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="p-8 text-center text-gray-500"><i class="fas fa-check-circle text-green-400 text-3xl mb-2"></i><p>OK</p></div>
        @endif
    </div>
    @endif

    {{-- ===================== SUPPLIER SECTION ===================== --}}
    @if($party === 'all' || $party === 'supplier')
    <div class="border-l-4 border-emerald-500 pl-2 {{ $party === 'all' ? 'mt-8' : '' }}">
        <h3 class="text-lg font-bold text-gray-700"><i class="fas fa-truck text-emerald-500 mr-2"></i>Nhà cung cấp</h3>
    </div>

    {{-- Supplier Debt Mismatches --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200" x-data="{ showHelp: false }">
        <div class="p-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                <i class="fas fa-money-bill-wave text-red-500 mr-2"></i>Công nợ NCC không khớp
                <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ count($results['supplier_debt_mismatches']) > 0 ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">{{ count($results['supplier_debt_mismatches']) }}</span>
                <i class="fas fa-info-circle text-blue-400 ml-2 cursor-pointer hover:text-blue-600 transition-colors" @click="showHelp = !showHelp"></i>
            </h3>
            <p class="text-sm text-gray-500 mt-1">PO.debt_amount ≠ (PO.total − tổng SupplierPaymentHistory)</p>
        </div>
        <div x-show="showHelp" x-transition class="p-4 bg-blue-50 border-b border-blue-100 text-sm text-blue-800">
            <p><strong>Hướng dẫn:</strong> Số tiền "Còn nợ" trên Đơn mua hàng không khớp với phép tính: Tổng PO trừ tổng thanh toán NCC.</p>
        </div>
        @if(count($results['supplier_debt_mismatches']) > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mã PO</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">NCC</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ngày</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Tổng</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Đã TT</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Nợ GN</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Nợ đúng</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Lệch</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($results['supplier_debt_mismatches'] as $item)
                    <tr class="hover:bg-red-50">
                        <td class="px-4 py-3 text-sm"><a href="{{ route('purchase-orders.show', $item['po_id']) }}" class="text-blue-600 hover:underline font-medium">{{ $item['po_code'] }}</a></td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $item['supplier_name'] }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $item['date'] }}</td>
                        <td class="px-4 py-3 text-sm text-right">{{ number_format($item['total'], 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-sm text-right text-green-600">{{ number_format($item['total_paid'], 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-sm text-right text-orange-600">{{ number_format($item['recorded_debt'], 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-sm text-right text-blue-600">{{ number_format($item['expected_debt'], 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-sm text-right font-semibold text-red-600">{{ number_format($item['difference'], 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="p-8 text-center text-gray-500"><i class="fas fa-check-circle text-green-400 text-3xl mb-2"></i><p>Tất cả công nợ NCC đều khớp</p></div>
        @endif
    </div>

    {{-- Supplier Paid Amount Mismatches --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200" x-data="{ showHelp: false }">
        <div class="p-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                <i class="fas fa-coins text-yellow-500 mr-2"></i>Số tiền TT NCC không khớp
                <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ count($results['supplier_paid_mismatches']) > 0 ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">{{ count($results['supplier_paid_mismatches']) }}</span>
                <i class="fas fa-info-circle text-blue-400 ml-2 cursor-pointer hover:text-blue-600 transition-colors" @click="showHelp = !showHelp"></i>
            </h3>
        </div>
        <div x-show="showHelp" x-transition class="p-4 bg-blue-50 border-b border-blue-100 text-sm text-blue-800">
            <p><strong>Hướng dẫn:</strong> PO.paid_amount không khớp với SUM(SupplierPaymentHistory.amount).</p>
        </div>
        @if(count($results['supplier_paid_mismatches']) > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mã PO</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">NCC</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ngày</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Tổng</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">GN đã TT</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Thực TT</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Lệch</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($results['supplier_paid_mismatches'] as $item)
                    <tr class="hover:bg-yellow-50">
                        <td class="px-4 py-3 text-sm"><a href="{{ route('purchase-orders.show', $item['po_id']) }}" class="text-blue-600 hover:underline font-medium">{{ $item['po_code'] }}</a></td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $item['supplier_name'] }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $item['date'] }}</td>
                        <td class="px-4 py-3 text-sm text-right">{{ number_format($item['total'], 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-sm text-right text-orange-600">{{ number_format($item['recorded_paid'], 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-sm text-right text-blue-600">{{ number_format($item['actual_paid'], 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-sm text-right font-semibold text-red-600">{{ number_format($item['difference'], 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="p-8 text-center text-gray-500"><i class="fas fa-check-circle text-green-400 text-3xl mb-2"></i><p>OK</p></div>
        @endif
    </div>

    {{-- Supplier Status Mismatches --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200" x-data="{ showHelp: false }">
        <div class="p-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                <i class="fas fa-tag text-indigo-500 mr-2"></i>Trạng thái TT NCC sai
                <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ count($results['supplier_status_mismatches']) > 0 ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">{{ count($results['supplier_status_mismatches']) }}</span>
                <i class="fas fa-info-circle text-blue-400 ml-2 cursor-pointer hover:text-blue-600 transition-colors" @click="showHelp = !showHelp"></i>
            </h3>
        </div>
        <div x-show="showHelp" x-transition class="p-4 bg-blue-50 border-b border-blue-100 text-sm text-blue-800">
            <p><strong>Hướng dẫn:</strong> Trạng thái thanh toán PO không tương ứng với paid_amount vs total.</p>
        </div>
        @if(count($results['supplier_status_mismatches']) > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mã PO</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">NCC</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Tổng</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Đã TT</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">TT hiện tại</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">TT đúng</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($results['supplier_status_mismatches'] as $item)
                    <tr class="hover:bg-indigo-50">
                        <td class="px-4 py-3 text-sm"><a href="{{ route('purchase-orders.show', $item['po_id']) }}" class="text-blue-600 hover:underline font-medium">{{ $item['po_code'] }}</a></td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $item['supplier_name'] }}</td>
                        <td class="px-4 py-3 text-sm text-right">{{ number_format($item['total'], 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-sm text-right">{{ number_format($item['paid_amount'], 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-sm"><span class="px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">{{ $item['recorded_status_label'] }}</span></td>
                        <td class="px-4 py-3 text-sm"><span class="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">{{ $item['expected_status_label'] }}</span></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="p-8 text-center text-gray-500"><i class="fas fa-check-circle text-green-400 text-3xl mb-2"></i><p>OK</p></div>
        @endif
    </div>
    @endif
</div>
@endsection
