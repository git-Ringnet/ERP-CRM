@extends('layouts.app')

@section('title', 'Chi tiết bảng giá NCC')
@section('page-title', $supplierPriceList->name)

@section('content')
<div class="space-y-4">
    <!-- Header Info -->
    <div class="bg-white rounded-lg shadow-sm p-4">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <span class="text-lg font-semibold text-gray-900">{{ $supplierPriceList->code }}</span>
                    @if($supplierPriceList->is_active)
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Hoạt động</span>
                    @else
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Tạm dừng</span>
                    @endif
                </div>
                <div class="text-sm text-gray-600 space-y-1">
                    <div><i class="fas fa-building w-5"></i> {{ $supplierPriceList->supplier->name }}</div>
                    <div><i class="fas fa-dollar-sign w-5"></i> {{ $supplierPriceList->currency }} 
                        @if($supplierPriceList->exchange_rate != 1)
                            (Tỷ giá: {{ number_format($supplierPriceList->exchange_rate, 0) }} VND)
                        @endif
                    </div>
                    <div><i class="fas fa-tag w-5"></i> {{ $supplierPriceList->price_type_label }}</div>
                </div>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('supplier-price-lists.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                    <i class="fas fa-arrow-left mr-2"></i>Quay lại
                </a>
            </div>
        </div>
    </div>

    <!-- Search & Filter -->
    <div class="bg-white rounded-lg shadow-sm p-4">
        <form method="GET" class="flex flex-col md:flex-row gap-3 items-end">
            <div class="flex-1">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Tìm SKU, tên sản phẩm..." 
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div class="w-full md:w-48">
                <select name="category" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">Tất cả danh mục</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}" {{ request('category') == $cat ? 'selected' : '' }} class="break-normal">{{ $cat }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark text-sm whitespace-nowrap">
                <i class="fas fa-search mr-1"></i>Tìm
            </button>
        </form>
    </div>

    <!-- Items Table -->
    <div class="bg-white rounded-lg shadow-sm">
        <div class="px-4 py-3 border-b bg-gray-50">
            <span class="font-medium text-sm">{{ number_format($items->total()) }} sản phẩm</span>
        </div>
        <div class="overflow-x-auto max-h-[60vh]">
            <table class="w-full text-sm table-fixed">
                <thead class="bg-gray-50 sticky top-0">
                    <tr>
                        <th class="px-3 py-2 text-left font-medium text-gray-500" style="width: 130px;">SKU</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-500" style="width: 240px;">Tên sản phẩm</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-500" style="width: 110px;">Danh mục</th>
                        <th class="px-3 py-2 text-right font-medium text-gray-500" style="width: 110px;">Giá (VND)</th>
                        <th class="px-3 py-2 text-right font-medium text-gray-500" style="width: 100px;">1yr (VND)</th>
                        <th class="px-3 py-2 text-right font-medium text-gray-500" style="width: 100px;">3yr (VND)</th>
                        <th class="px-3 py-2 text-right font-medium text-gray-500" style="width: 100px;">5yr (VND)</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-500" style="width: 100px;">Sheet</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @php
                        $exchangeRate = $supplierPriceList->exchange_rate ?? 1;
                    @endphp
                    @forelse($items as $item)
                    <tr class="hover:bg-gray-50">
                        <td class="px-3 py-2 font-mono text-blue-600" title="{{ $item->sku }}">
                            <div class="truncate">{{ $item->sku }}</div>
                        </td>
                        <td class="px-3 py-2">
                            <div class="font-medium truncate" title="{{ $item->product_name }}">{{ Str::limit($item->product_name, 40) }}</div>
                            @if($item->description)
                                <div class="text-gray-400 truncate text-xs" title="{{ $item->description }}">{{ Str::limit($item->description, 50) }}</div>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-gray-600" title="{{ $item->category }}">
                            <div class="line-clamp-2 text-xs">{{ $item->category ?? '-' }}</div>
                        </td>
                        <td class="px-3 py-2 text-right font-medium whitespace-nowrap">
                            {{ $item->list_price ? number_format($item->list_price * $exchangeRate, 0) : '-' }}
                        </td>
                        <td class="px-3 py-2 text-right text-gray-600 whitespace-nowrap">
                            {{ $item->price_1yr ? number_format($item->price_1yr * $exchangeRate, 0) : '-' }}
                        </td>
                        <td class="px-3 py-2 text-right text-gray-600 whitespace-nowrap">
                            {{ $item->price_3yr ? number_format($item->price_3yr * $exchangeRate, 0) : '-' }}
                        </td>
                        <td class="px-3 py-2 text-right text-gray-600 whitespace-nowrap">
                            {{ $item->price_5yr ? number_format($item->price_5yr * $exchangeRate, 0) : '-' }}
                        </td>
                        <td class="px-3 py-2 text-gray-400" title="{{ $item->source_sheet }}">
                            <div class="truncate text-xs">{{ $item->source_sheet }}</div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-gray-500">Không có sản phẩm nào</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($items->hasPages())
        <div class="px-4 py-3 border-t text-sm">
            {{ $items->appends(request()->query())->links() }}
        </div>
        @endif
    </div>

    <!-- Import Log -->
    @if($supplierPriceList->import_log)
    <div class="bg-white rounded-lg shadow-sm p-4">
        <h4 class="font-medium text-gray-900 mb-2 text-sm"><i class="fas fa-history mr-2"></i>Thông tin Import</h4>
        <div class="text-xs text-gray-600">
            <span>Ngày: {{ \Carbon\Carbon::parse($supplierPriceList->import_log['imported_at'])->format('d/m/Y H:i') }}</span>
            <span class="mx-2">|</span>
            <span>Tổng: {{ number_format($supplierPriceList->import_log['total_items'] ?? 0) }}</span>
            <span class="mx-2">|</span>
            <span>Tạo mới: {{ number_format($supplierPriceList->import_log['created'] ?? 0) }}</span>
            <span class="mx-2">|</span>
            <span>Bỏ qua: {{ number_format($supplierPriceList->import_log['skipped'] ?? 0) }}</span>
        </div>
    </div>
    @endif
</div>

<style>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
@endsection
