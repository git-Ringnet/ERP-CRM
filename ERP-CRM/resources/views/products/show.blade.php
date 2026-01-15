@extends('layouts.app')

@section('title', 'Chi tiết sản phẩm')
@section('page-title', 'Chi tiết sản phẩm: ' . $product->name)

@section('content')
<div class="space-y-4">
    <div class="flex items-center justify-between">
        <a href="{{ route('products.index') }}" class="inline-flex items-center px-3 py-1.5 text-sm bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200">
            <i class="fas fa-arrow-left mr-2"></i>Quay lại
        </a>
        <div class="flex gap-2">
            <a href="{{ route('products.edit', $product->id) }}" class="inline-flex items-center px-3 py-1.5 text-sm bg-yellow-500 text-white rounded-md hover:bg-yellow-600">
                <i class="fas fa-edit mr-2"></i>Sửa
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 space-y-4">
            <!-- Thông tin cơ bản -->
            <!-- Requirements: 6.3 -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-4 py-3 border-b border-gray-200">
                    <h2 class="text-base font-semibold text-gray-800"><i class="fas fa-box mr-2 text-primary"></i>Thông tin cơ bản</h2>
                </div>
                <div class="p-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Mã sản phẩm</label>
                            <p class="text-sm text-gray-900 font-semibold">{{ $product->code }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Tên sản phẩm</label>
                            <p class="text-sm text-gray-900">{{ $product->name }}</p>
                        </div>
                        <div class="hidden">
                            <label class="block text-sm font-medium text-gray-500">Danh mục</label>
                            <p class="text-sm">
                                @if($product->category)
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">{{ $product->category }}</span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Đơn vị</label>
                            <p class="text-sm text-gray-900">{{ $product->unit }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Bảo hành</label>
                            <p class="text-sm text-gray-900">
                                @if($product->warranty_months)
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                        <i class="fas fa-shield-alt mr-1"></i>{{ $product->warranty_months }} tháng
                                    </span>
                                @else
                                    <span class="text-gray-400">Không có bảo hành</span>
                                @endif
                            </p>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-500">Mô tả</label>
                            <p class="text-sm text-gray-900">{{ $product->description ?: '-' }}</p>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-500">Ghi chú</label>
                            <p class="text-sm text-gray-900">{{ $product->note ?: '-' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Danh sách Product Items -->
            <!-- Requirements: 3.5, 6.4 -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
                    <h2 class="text-base font-semibold text-gray-800"><i class="fas fa-list mr-2 text-primary"></i>Danh sách SKU / Items</h2>
                    <span class="text-sm text-gray-500">Tổng: {{ $product->items->count() }} items</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">SKU</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mô tả</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Giá nhập (USD)</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Gói giá</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">SL</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($product->items as $item)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="font-medium text-gray-900 {{ Str::startsWith($item->sku, 'NOSKU') ? 'text-gray-400 italic' : '' }}">
                                        {{ $item->sku }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-500">{{ Str::limit($item->description, 30) }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-right font-medium">${{ number_format($item->cost_usd, 2) }}</td>
                                <td class="px-4 py-3 text-sm">
                                    @if($item->price_tiers && is_array($item->price_tiers))
                                        <div class="flex flex-wrap gap-1">
                                            @foreach($item->price_tiers as $tier)
                                                <span class="px-2 py-1 text-xs bg-blue-50 text-blue-700 rounded">
                                                    {{ $tier['name'] ?? 'N/A' }}: ${{ number_format($tier['price'] ?? 0, 2) }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-center text-sm font-semibold">{{ $item->quantity }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-center">
                                    @switch($item->status)
                                        @case('in_stock')
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Trong kho</span>
                                            @break
                                        @case('sold')
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">Đã bán</span>
                                            @break
                                        @case('damaged')
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Hư hỏng</span>
                                            @break
                                        @case('transferred')
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Đã chuyển</span>
                                            @break
                                        @case('liquidation')
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-indigo-100 text-indigo-800">Thanh lý</span>
                                            @break
                                        @default
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">{{ $item->status }}</span>
                                    @endswitch
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="10" class="px-4 py-8 text-center text-gray-500">
                                    <i class="fas fa-inbox text-4xl mb-2"></i>
                                    <p>Chưa có item nào. Thực hiện nhập kho để tạo items.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="space-y-4">
            <!-- Thống kê -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-4 py-3 border-b border-gray-200">
                    <h2 class="text-base font-semibold text-gray-800"><i class="fas fa-chart-bar mr-2 text-primary"></i>Thống kê</h2>
                </div>
                <div class="p-4 space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-500">Tổng số items:</span>
                        <span class="text-sm font-semibold text-gray-900">{{ $product->items->count() }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-500">Trong kho:</span>
                        <span class="text-sm font-semibold text-green-600">{{ $product->items->where('status', 'in_stock')->sum('quantity') }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-500">Đã bán:</span>
                        <span class="text-sm font-semibold text-blue-600">{{ $product->items->where('status', 'sold')->sum('quantity') }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-500">Hư hỏng:</span>
                        <span class="text-sm font-semibold text-red-600">{{ $product->items->where('status', 'damaged')->sum('quantity') }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-500">Thanh lý:</span>
                        <span class="text-sm font-semibold text-indigo-600">{{ $product->items->where('status', 'liquidation')->sum('quantity') }}</span>
                    </div>
                </div>
            </div>

            <!-- Thông tin thời gian -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-4 py-3 border-b border-gray-200">
                    <h2 class="text-base font-semibold text-gray-800"><i class="fas fa-clock mr-2 text-primary"></i>Thời gian</h2>
                </div>
                <div class="p-4 space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-500">Ngày tạo:</span>
                        <span class="text-sm text-gray-900">{{ $product->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-500">Cập nhật:</span>
                        <span class="text-sm text-gray-900">{{ $product->updated_at->format('d/m/Y H:i') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
