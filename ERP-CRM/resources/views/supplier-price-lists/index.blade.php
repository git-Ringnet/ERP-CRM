@extends('layouts.app')

@section('title', 'Bảng giá')
@section('page-title', 'Bảng giá')

@section('content')
    <div class="bg-white rounded-lg shadow-sm">
        <!-- Header -->
        <div class="p-4 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex flex-col sm:flex-row gap-4 flex-1">
                <!-- Search -->
                <div class="relative flex-1 max-w-md">
                    <form action="{{ route('supplier-price-lists.index') }}" method="GET" class="flex">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Tìm kiếm..."
                            class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                        <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    </form>
                </div>

                <!-- Filter by Supplier -->
                <select name="supplier_id"
                    onchange="window.location.href='{{ route('supplier-price-lists.index') }}?supplier_id='+this.value+'&search={{ request('search') }}'"
                    class="border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="">Tất cả NCC</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}>
                            {{ $supplier->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex gap-2">
                <a href="{{ route('supplier-price-lists.import') }}"
                    class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                    <i class="fas fa-file-import mr-2"></i>
                    Import Excel
                </a>
            </div>
        </div>

        <!-- Info Box -->
        <div class="mx-4 mt-4 bg-blue-50 border-l-4 border-blue-500 p-3 rounded">
            <p class="text-blue-700 text-sm">
                <i class="fas fa-info-circle mr-2"></i>
                Import bảng giá từ file Excel (Fortinet, Cisco, HP...), sau đó cấu hình công thức tính giá bán (chiết khấu, margin, shipping) để tự động tính giá final.
            </p>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mã</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tên bảng giá</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nhà cung cấp</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Loại giá</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Tiền tệ</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Số SP</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Hiệu lực</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Trạng thái</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($priceLists as $priceList)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 whitespace-nowrap">
                                <a href="{{ route('supplier-price-lists.show', $priceList) }}"
                                    class="font-medium text-blue-600 hover:text-blue-800">
                                    {{ $priceList->code }}
                                </a>
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-sm font-medium text-gray-900">{{ $priceList->name }}</div>
                                @if($priceList->file_name)
                                    <div class="text-xs text-gray-500"><i class="fas fa-file-excel text-green-600"></i>
                                        {{ $priceList->file_name }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                {{ $priceList->supplier->name }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-center">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800">
                                    {{ $priceList->price_type_label }}
                                </span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-center text-sm">
                                {{ $priceList->currency }}
                                @if($priceList->exchange_rate != 1)
                                    <div class="text-xs text-gray-500">x {{ number_format($priceList->exchange_rate, 0) }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-center text-sm font-medium">
                                {{ number_format($priceList->items_count) }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                @if($priceList->effective_date || $priceList->expiry_date)
                                    {{ $priceList->effective_date?->format('d/m/Y') ?? '...' }}
                                    - {{ $priceList->expiry_date?->format('d/m/Y') ?? '...' }}
                                @else
                                    <span class="text-gray-400">Không giới hạn</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-center">
                                @if($priceList->is_active && $priceList->isValid())
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Hoạt
                                        động</span>
                                @elseif(!$priceList->is_active)
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Tạm
                                        dừng</span>
                                @else
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Hết
                                        hạn</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <a href="{{ route('supplier-price-lists.show', $priceList) }}"
                                        class="p-2 text-blue-600 hover:bg-blue-50 rounded" title="Xem chi tiết">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('supplier-price-lists.show', $priceList) }}#apply"
                                        class="p-2 text-green-600 hover:bg-green-50 rounded" title="Áp dụng giá vào kho">
                                        <i class="fas fa-sync-alt"></i>
                                    </a>
                                    <form action="{{ route('supplier-price-lists.toggle', $priceList) }}" method="POST"
                                        class="inline">
                                        @csrf
                                        <button type="submit"
                                            class="p-2 {{ $priceList->is_active ? 'text-gray-600 hover:bg-gray-50' : 'text-green-600 hover:bg-green-50' }} rounded"
                                            title="{{ $priceList->is_active ? 'Tắt' : 'Bật' }}">
                                            <i class="fas {{ $priceList->is_active ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                                        </button>
                                    </form>
                                    <form action="{{ route('supplier-price-lists.destroy', $priceList) }}" method="POST"
                                        class="inline" onsubmit="return confirm('Bạn có chắc muốn xóa bảng giá này?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-2 text-red-600 hover:bg-red-50 rounded" title="Xóa">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-8 text-center text-gray-500">
                                <i class="fas fa-file-excel text-4xl mb-2 text-gray-300"></i>
                                <p>Chưa có bảng giá nào</p>
                                <a href="{{ route('supplier-price-lists.import') }}"
                                    class="text-blue-600 hover:underline mt-2 inline-block">
                                    <i class="fas fa-plus mr-1"></i>Import bảng giá đầu tiên
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($priceLists->hasPages())
            <div class="px-4 py-3 border-t border-gray-200">
                {{ $priceLists->appends(request()->query())->links() }}
            </div>
        @endif
    </div>
@endsection