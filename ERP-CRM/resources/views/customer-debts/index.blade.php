@extends('layouts.app')

@section('title', 'Quản lý công nợ khách hàng')
@section('page-title', 'Quản lý công nợ khách hàng')

@section('content')
    <div class="space-y-6">
        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                        <i class="fas fa-users text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">KH có công nợ</p>
                        <p class="text-2xl font-bold text-gray-800">
                            {{ number_format($summary['total_customers_with_debt']) }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-red-100 text-red-600">
                        <i class="fas fa-money-bill-wave text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">Tổng công nợ</p>
                        <p class="text-2xl font-bold text-red-600">{{ number_format($summary['total_debt'], 0, ',', '.') }}đ
                        </p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-orange-100 text-orange-600">
                        <i class="fas fa-exclamation-circle text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">Nợ quá hạn</p>
                        <p class="text-2xl font-bold text-orange-600">
                            {{ number_format($summary['total_overdue'], 0, ',', '.') }}đ</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow p-4">
            <form method="GET" class="flex flex-wrap gap-4 items-end">
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
        </div>

        <!-- Action Buttons -->
        <div class="flex justify-between items-center">
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
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Hạn mức</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">ĐH chưa TT</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($customers as $customer)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $customer->code }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900">{{ $customer->name }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ $customer->phone }}</td>
                                <td class="px-4 py-3 text-sm text-right text-gray-900">
                                    {{ number_format($customer->total_sales, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-sm text-right text-green-600">
                                    {{ number_format($customer->total_paid, 0, ',', '.') }}</td>
                                <td
                                    class="px-4 py-3 text-sm text-right font-medium {{ $customer->total_debt > 0 ? 'text-red-600' : 'text-gray-600' }}">
                                    {{ number_format($customer->total_debt, 0, ',', '.') }}
                                </td>
                                <td
                                    class="px-4 py-3 text-sm text-right {{ $customer->debt_limit > 0 && $customer->total_debt > $customer->debt_limit ? 'text-orange-600 font-medium' : 'text-gray-600' }}">
                                    {{ $customer->debt_limit ? number_format($customer->debt_limit, 0, ',', '.') : '-' }}
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
                        @empty
                            <tr>
                                <td colspan="9" class="px-4 py-8 text-center text-gray-500">
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