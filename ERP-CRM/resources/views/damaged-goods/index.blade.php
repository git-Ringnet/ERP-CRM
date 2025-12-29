@extends('layouts.app')

@section('title', 'Hàng hư hỏng')
@section('page-title', 'Hàng Hư Hỏng / Thanh Lý')

@section('content')
<div class="bg-white rounded-lg shadow-sm">
    <!-- Header -->
    <div class="p-3 sm:p-4 border-b border-gray-200 space-y-3">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <!-- Search -->
            <div class="relative">
                <form action="{{ route('damaged-goods.index') }}" method="GET" class="flex">
                    <input type="text" name="search" value="{{ request('search') }}" 
                           placeholder="Tìm kiếm mã báo cáo..." 
                           class="w-full pl-10 pr-4 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                    <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    <input type="hidden" name="type" value="{{ request('type') }}">
                    <input type="hidden" name="status" value="{{ request('status') }}">
                </form>
            </div>
            
            <!-- Filter by Type -->
            <select name="type" onchange="window.location.href='{{ route('damaged-goods.index') }}?type='+this.value+'&status={{ request('status') }}&search={{ request('search') }}'" 
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                <option value="">Tất cả loại</option>
                <option value="damaged" {{ request('type') == 'damaged' ? 'selected' : '' }}>Hàng hư hỏng</option>
                <option value="liquidation" {{ request('type') == 'liquidation' ? 'selected' : '' }}>Thanh lý</option>
            </select>

            <!-- Filter by Status -->
            <select name="status" onchange="window.location.href='{{ route('damaged-goods.index') }}?status='+this.value+'&type={{ request('type') }}&search={{ request('search') }}'" 
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                <option value="">Tất cả trạng thái</option>
                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Chờ duyệt</option>
                <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Đã duyệt</option>
                <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Từ chối</option>
                <option value="processed" {{ request('status') == 'processed' ? 'selected' : '' }}>Đã xử lý</option>
            </select>

            <!-- Action Buttons -->
            <div class="flex gap-2">
                <a href="{{ route('damaged-goods.export', request()->query()) }}" 
                   class="inline-flex items-center justify-center px-4 py-2 bg-emerald-500 text-white rounded-lg hover:bg-emerald-600 transition-colors text-sm">
                    <i class="fas fa-file-excel mr-2"></i>Xuất Excel
                </a>
                <a href="{{ route('damaged-goods.create') }}" 
                   class="inline-flex items-center justify-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors text-sm">
                    <i class="fas fa-plus mr-2"></i>Tạo Báo Cáo
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="mx-4 mt-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mx-4 mt-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
            {{ session('error') }}
        </div>
    @endif

    <!-- Table - Desktop View -->
    <div class="hidden md:block overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">STT</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mã</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Loại</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sản phẩm</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Số lượng</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Giá trị gốc</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thu hồi</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tổn thất</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ngày phát hiện</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Trạng thái</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Thao tác</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($damagedGoods as $item)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 whitespace-nowrap text-center text-sm text-gray-500">
                        {{ ($damagedGoods->currentPage() - 1) * $damagedGoods->perPage() + $loop->iteration }}
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        <a href="{{ route('damaged-goods.show', $item) }}" class="font-medium text-primary hover:underline">
                            {{ $item->code }}
                        </a>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $item->type === 'damaged' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800' }}">
                            {{ $item->getTypeLabel() }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-900">{{ $item->product->name }}</td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ number_format($item->quantity, 2) }}</td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ number_format($item->original_value, 0) }}đ</td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ number_format($item->recovery_value, 0) }}đ</td>
                    <td class="px-4 py-3 text-sm text-red-600 font-medium">{{ number_format($item->getLossAmount(), 0) }}đ</td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ $item->discovery_date->format('d/m/Y') }}</td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        @if($item->status == 'pending')
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">{{ $item->getStatusLabel() }}</span>
                        @elseif($item->status == 'approved')
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">{{ $item->getStatusLabel() }}</span>
                        @elseif($item->status == 'rejected')
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">{{ $item->getStatusLabel() }}</span>
                        @else
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">{{ $item->getStatusLabel() }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-center">
                        <div class="flex items-center justify-center gap-2">
                            <a href="{{ route('damaged-goods.show', $item) }}" 
                               class="p-2 text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 hover:text-blue-700 transition-colors" 
                               title="Xem">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('damaged-goods.edit', $item) }}" 
                               class="p-2 text-yellow-600 bg-yellow-50 rounded-lg hover:bg-yellow-100 hover:text-yellow-700 transition-colors" 
                               title="Sửa">
                                <i class="fas fa-edit"></i>
                            </a>
                            @if($item->status !== 'processed')
                                <form action="{{ route('damaged-goods.destroy', $item) }}" method="POST" class="inline" onsubmit="return confirm('Bạn có chắc muốn xóa?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" 
                                            class="p-2 text-red-600 bg-red-50 rounded-lg hover:bg-red-100 hover:text-red-700 transition-colors" 
                                            title="Xóa">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="11" class="px-4 py-8 text-center text-gray-500">
                        <i class="fas fa-exclamation-triangle text-4xl mb-2"></i>
                        <p>Không có dữ liệu</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Card View - Mobile -->
    <div class="md:hidden divide-y divide-gray-200">
        @forelse($damagedGoods as $item)
        <div class="p-4 hover:bg-gray-50">
            <div class="flex justify-between items-start mb-2">
                <div class="flex-1">
                    <a href="{{ route('damaged-goods.show', $item) }}" class="font-medium text-primary hover:underline">
                        {{ $item->code }}
                    </a>
                    <div class="text-sm text-gray-500">{{ $item->product->name }}</div>
                </div>
                @if($item->status == 'pending')
                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">{{ $item->getStatusLabel() }}</span>
                @elseif($item->status == 'approved')
                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">{{ $item->getStatusLabel() }}</span>
                @elseif($item->status == 'rejected')
                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">{{ $item->getStatusLabel() }}</span>
                @else
                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">{{ $item->getStatusLabel() }}</span>
                @endif
            </div>
            <div class="mb-2">
                <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $item->type === 'damaged' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800' }}">
                    {{ $item->getTypeLabel() }}
                </span>
            </div>
            <div class="space-y-1 text-sm text-gray-600 mb-3">
                <div><i class="fas fa-cubes w-4"></i> Số lượng: {{ number_format($item->quantity, 2) }}</div>
                <div><i class="fas fa-money-bill w-4"></i> Giá trị gốc: {{ number_format($item->original_value, 0) }}đ</div>
                <div><i class="fas fa-undo w-4"></i> Thu hồi: {{ number_format($item->recovery_value, 0) }}đ</div>
                <div class="text-red-600"><i class="fas fa-chart-line w-4"></i> Tổn thất: {{ number_format($item->getLossAmount(), 0) }}đ</div>
                <div><i class="fas fa-calendar w-4"></i> Ngày: {{ $item->discovery_date->format('d/m/Y') }}</div>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('damaged-goods.show', $item) }}" 
                   class="flex-1 text-center px-3 py-2 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 text-sm">
                    <i class="fas fa-eye mr-1"></i>Xem
                </a>
                <a href="{{ route('damaged-goods.edit', $item) }}" 
                   class="flex-1 text-center px-3 py-2 bg-yellow-100 text-yellow-700 rounded-lg hover:bg-yellow-200 text-sm">
                    <i class="fas fa-edit mr-1"></i>Sửa
                </a>
                @if($item->status !== 'processed')
                    <form action="{{ route('damaged-goods.destroy', $item) }}" method="POST" class="flex-1" onsubmit="return confirm('Bạn có chắc muốn xóa?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full px-3 py-2 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 text-sm">
                            <i class="fas fa-trash mr-1"></i>Xóa
                        </button>
                    </form>
                @endif
            </div>
        </div>
        @empty
        <div class="p-8 text-center text-gray-500">
            <i class="fas fa-exclamation-triangle text-4xl mb-2"></i>
            <p>Không có dữ liệu</p>
        </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($damagedGoods->hasPages())
    <div class="px-4 py-3 border-t border-gray-200">
        {{ $damagedGoods->appends(request()->query())->links() }}
    </div>
    @endif
</div>
@endsection
