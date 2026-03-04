@extends('layouts.app')

@section('title', 'Quản lý Thu Chi')
@section('page-title', 'Quản lý Thu Chi')

@section('content')
<div class="space-y-6">
    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-green-500">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 text-green-600">
                    <i class="fas fa-arrow-down text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Tổng Thu (Kỳ này)</p>
                    <p class="text-2xl font-bold text-green-600">{{ number_format($incomeTotal, 0, ',', '.') }}đ</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-red-500">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-red-100 text-red-600">
                    <i class="fas fa-arrow-up text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Tổng Chi (Kỳ này)</p>
                    <p class="text-2xl font-bold text-red-600">{{ number_format($expenseTotal, 0, ',', '.') }}đ</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-blue-500">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                    <i class="fas fa-balance-scale text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Cân đối</p>
                    <p class="text-2xl font-bold {{ $incomeTotal - $expenseTotal >= 0 ? 'text-blue-600' : 'text-red-600' }}">
                        {{ number_format($incomeTotal - $expenseTotal, 0, ',', '.') }}đ
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters & Actions -->
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex flex-col md:flex-row justify-between gap-4 items-end">
            <form method="GET" class="flex flex-wrap gap-4 items-end flex-1">
                <div class="w-40">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Từ ngày</label>
                    <input type="date" name="date_from" value="{{ $dateFrom }}"
                        class="w-full border border-gray-300 rounded-lg px-3 py-1.5 focus:ring-2 focus:ring-primary">
                </div>
                <div class="w-40">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Đến ngày</label>
                    <input type="date" name="date_to" value="{{ $dateTo }}"
                        class="w-full border border-gray-300 rounded-lg px-3 py-1.5 focus:ring-2 focus:ring-primary">
                </div>
                <div class="w-40">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Loại</label>
                    <select name="type" class="w-full border border-gray-300 rounded-lg px-3 py-1.5 focus:ring-2 focus:ring-primary">
                        <option value="">Tất cả</option>
                        <option value="income" {{ $type === 'income' ? 'selected' : '' }}>Thu</option>
                        <option value="expense" {{ $type === 'expense' ? 'selected' : '' }}>Chi</option>
                    </select>
                </div>
                <div class="w-48">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Danh mục</label>
                    <select name="category_id" class="w-full border border-gray-300 rounded-lg px-3 py-1.5 focus:ring-2 focus:ring-primary">
                        <option value="">Tất cả danh mục</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ $categoryId == $category->id ? 'selected' : '' }}>
                                [{{ $category->type === 'income' ? 'Thu' : 'Chi' }}] {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="bg-primary text-white px-4 py-1.5 rounded-lg hover:bg-primary/90">
                        <i class="fas fa-search mr-1"></i> Lọc
                    </button>
                    <a href="{{ route('financial-transactions.index') }}" class="bg-gray-200 text-gray-700 px-4 py-1.5 rounded-lg hover:bg-gray-300">
                        <i class="fas fa-redo mr-1"></i> Reset
                    </a>
                </div>
            </form>
            <div class="flex gap-2">
                <a href="{{ route('financial-transactions.categories') }}" class="bg-gray-100 text-gray-700 px-4 py-1.5 rounded-lg hover:bg-gray-200 border border-gray-300">
                    <i class="fas fa-tags mr-1"></i> Danh mục
                </a>
                <a href="{{ route('financial-transactions.create') }}" class="bg-primary text-white px-4 py-1.5 rounded-lg hover:bg-primary/90">
                    <i class="fas fa-plus mr-1"></i> Thêm giao dịch
                </a>
            </div>
        </div>
    </div>

    <!-- Transaction Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ngày</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Loại</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Danh mục</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Số tiền</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">PTTT</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ghi chú</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($transactions as $transaction)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm text-gray-900">{{ $transaction->date->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-sm">
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $transaction->type === 'income' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $transaction->type_label }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-900">{{ $transaction->category->name }}</td>
                        <td class="px-4 py-3 text-sm font-bold {{ $transaction->type === 'income' ? 'text-green-600' : 'text-red-600' }}">
                            {{ number_format($transaction->amount, 0, ',', '.') }}đ
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $transaction->payment_method_label }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500 max-w-xs truncate" title="{{ $transaction->note }}">
                            {{ $transaction->note ?? '-' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-center">
                            <div class="flex justify-center space-x-2">
                                <a href="{{ route('financial-transactions.edit', $transaction) }}" class="text-blue-600 hover:text-blue-900">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('financial-transactions.destroy', $transaction) }}" method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn xóa?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                            <i class="fas fa-receipt text-4xl mb-3 opacity-20"></i>
                            <p>Không có dữ liệu giao dịch</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-gray-200">
            {{ $transactions->links() }}
        </div>
    </div>
</div>
@endsection
