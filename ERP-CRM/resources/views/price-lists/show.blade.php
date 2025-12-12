@extends('layouts.app')

@section('title', 'Chi tiết bảng giá - ' . $priceList->code)
@section('page-title', 'Chi tiết bảng giá: ' . $priceList->code)

@section('content')
@if(session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">{{ session('success') }}</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <!-- Info -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="p-4 border-b border-gray-200 flex justify-between items-center">
                    <h3 class="text-lg font-semibold">Thông tin bảng giá</h3>
                    <span class="px-3 py-1 text-sm rounded-full {{ $priceList->type_color }}">
                        {{ $priceList->type_label }}
                    </span>
                </div>
                <div class="p-4">

                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-gray-500">Mã:</span>
                            <span class="font-medium ml-2">{{ $priceList->code }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Tên:</span>
                            <span class="font-medium ml-2">{{ $priceList->name }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Khách hàng:</span>
                            <span class="font-medium ml-2">{{ $priceList->customer ? $priceList->customer->name : 'Tất cả' }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Trạng thái:</span>
                            @if($priceList->is_active && $priceList->isValid())
                                <span class="ml-2 px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Hoạt động</span>
                            @elseif(!$priceList->is_active)
                                <span class="ml-2 px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800">Tạm dừng</span>
                            @else
                                <span class="ml-2 px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">Hết hạn</span>
                            @endif
                        </div>
                        <div>
                            <span class="text-gray-500">Thời gian:</span>
                            <span class="font-medium ml-2">
                                @if($priceList->start_date || $priceList->end_date)
                                    {{ $priceList->start_date ? $priceList->start_date->format('d/m/Y') : '...' }}
                                    - {{ $priceList->end_date ? $priceList->end_date->format('d/m/Y') : '...' }}
                                @else
                                    Không giới hạn
                                @endif
                            </span>
                        </div>
                        <div>
                            <span class="text-gray-500">Chiết khấu chung:</span>
                            <span class="font-medium ml-2">{{ $priceList->discount_percent }}%</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Độ ưu tiên:</span>
                            <span class="font-medium ml-2">{{ $priceList->priority }}</span>
                        </div>
                        @if($priceList->description)
                        <div class="col-span-2">
                            <span class="text-gray-500">Mô tả:</span>
                            <p class="mt-1">{{ $priceList->description }}</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Products -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="p-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold">Chi tiết giá sản phẩm ({{ $priceList->items->count() }} sản phẩm)</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sản phẩm</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Giá gốc</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Giá bán</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">SL tối thiểu</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">CK (%)</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Giá cuối</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($priceList->items as $index => $item)
                            <tr>
                                <td class="px-4 py-3 text-sm">{{ $index + 1 }}</td>
                                <td class="px-4 py-3">
                                    <div class="font-medium">{{ $item->product->name ?? 'N/A' }}</div>
                                    <div class="text-xs text-gray-500">{{ $item->product->code ?? '' }}</div>
                                </td>
                                <td class="px-4 py-3 text-right text-gray-500">{{ number_format($item->product->price ?? 0, 0, ',', '.') }} đ</td>
                                <td class="px-4 py-3 text-right">{{ number_format($item->price, 0, ',', '.') }} đ</td>
                                <td class="px-4 py-3 text-center">{{ $item->min_quantity }}</td>
                                <td class="px-4 py-3 text-center">{{ $item->discount_percent }}%</td>
                                <td class="px-4 py-3 text-right font-medium text-green-600">{{ number_format($item->final_price, 0, ',', '.') }} đ</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <div class="bg-white rounded-lg shadow-sm">
                <div class="p-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold">Thao tác</h3>
                </div>
                <div class="p-4 space-y-3">
                    <a href="{{ route('price-lists.index') }}" class="w-full inline-flex items-center justify-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                        <i class="fas fa-arrow-left mr-2"></i> Quay lại
                    </a>
                    <a href="{{ route('price-lists.edit', $priceList) }}" class="w-full inline-flex items-center justify-center px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded-lg transition-colors">
                        <i class="fas fa-edit mr-2"></i> Chỉnh sửa
                    </a>
                    <form action="{{ route('price-lists.toggle', $priceList) }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2 {{ $priceList->is_active ? 'bg-gray-500 hover:bg-gray-600' : 'bg-green-600 hover:bg-green-700' }} text-white rounded-lg transition-colors">
                            <i class="fas {{ $priceList->is_active ? 'fa-toggle-off' : 'fa-toggle-on' }} mr-2"></i>
                            {{ $priceList->is_active ? 'Tạm dừng' : 'Kích hoạt' }}
                        </button>
                    </form>
                    <form action="{{ route('price-lists.destroy', $priceList) }}" method="POST" onsubmit="return confirm('Bạn có chắc muốn xóa bảng giá này?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2 bg-red-100 hover:bg-red-200 text-red-700 rounded-lg transition-colors">
                            <i class="fas fa-trash mr-2"></i> Xóa bảng giá
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
