@extends('layouts.app')

@section('title', 'Báo giá NCC')
@section('page-title', 'Báo giá từ nhà cung cấp')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <p class="text-gray-600">Quản lý báo giá nhận từ nhà cung cấp</p>
        <a href="{{ route('supplier-quotations.create') }}" class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark">
            <i class="fas fa-plus mr-2"></i> Nhập báo giá NCC
        </a>
    </div>

    <div class="bg-white rounded-lg shadow p-4 border-b border-gray-200 bg-gray-50">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Tìm kiếm..." 
                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
            <select name="status" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg">
                <option value="">-- Tất cả trạng thái --</option>
                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Chờ xử lý</option>
                <option value="selected" {{ request('status') == 'selected' ? 'selected' : '' }}>Đã chọn</option>
                <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Từ chối</option>
            </select>
            <select name="supplier_id" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg">
                <option value="">-- Tất cả NCC --</option>
                @foreach($suppliers as $supplier)
                    <option value="{{ $supplier->id }}" {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}>{{ $supplier->name }}</option>
                @endforeach
            </select>
            <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
                <i class="fas fa-search mr-2"></i> Lọc
            </button>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mã</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Yêu cầu</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nhà cung cấp</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ngày báo giá</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Hiệu lực đến</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Tổng tiền</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Trạng thái</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($quotations as $quotation)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium text-primary">{{ $quotation->code }}</td>
                        <td class="px-4 py-3">{{ $quotation->purchaseRequest?->code ?? '-' }}</td>
                        <td class="px-4 py-3">{{ $quotation->supplier->name }}</td>
                        <td class="px-4 py-3">{{ $quotation->quotation_date->format('d/m/Y') }}</td>
                        <td class="px-4 py-3">
                            @if($quotation->valid_until < now())
                                <span class="text-red-600">{{ $quotation->valid_until->format('d/m/Y') }} (Hết hạn)</span>
                            @else
                                {{ $quotation->valid_until->format('d/m/Y') }}
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right font-semibold">{{ number_format($quotation->total) }}đ</td>
                        <td class="px-4 py-3">
                            @if($quotation->status == 'pending')
                                <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">Chờ xử lý</span>
                            @elseif($quotation->status == 'selected')
                                <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Đã chọn</span>
                            @else
                                <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">Từ chối</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center space-x-1">
                                <a href="{{ route('supplier-quotations.show', $quotation) }}" class="inline-flex items-center justify-center w-8 h-8 bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200" title="Xem">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @if($quotation->status == 'pending')
                                    <a href="{{ route('supplier-quotations.edit', $quotation) }}" class="inline-flex items-center justify-center w-8 h-8 bg-yellow-100 text-yellow-600 rounded-lg hover:bg-yellow-200" title="Sửa">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('supplier-quotations.select', $quotation) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center justify-center w-8 h-8 bg-green-100 text-green-600 rounded-lg hover:bg-green-200" title="Chọn báo giá này" onclick="return confirm('Chọn báo giá này để tạo PO?')">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </form>
                                    <form action="{{ route('supplier-quotations.reject', $quotation) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center justify-center w-8 h-8 bg-red-100 text-red-600 rounded-lg hover:bg-red-200" title="Từ chối" onclick="return confirm('Từ chối báo giá này?')">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-gray-500">Chưa có báo giá nào</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t">{{ $quotations->links() }}</div>
    </div>
</div>
@endsection
