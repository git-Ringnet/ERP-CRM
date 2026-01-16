@extends('layouts.app')

@section('title', 'Báo giá')
@section('page-title', 'Quản lý Báo giá')

@section('content')
    <div class="bg-white rounded-lg shadow-sm">
        <!-- Header -->
        <div class="p-4 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex flex-col sm:flex-row gap-4 flex-1">
                <!-- Search -->
                <div class="relative flex-1 max-w-md">
                    <form action="{{ route('quotations.index') }}" method="GET" class="flex">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Tìm kiếm báo giá..."
                            class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                        <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                        <input type="hidden" name="status" value="{{ request('status') }}">
                    </form>
                </div>

                <!-- Filter by Status -->
                <div class="flex items-center gap-2">
                    <select name="status"
                        onchange="window.location.href='{{ route('quotations.index') }}?status='+this.value+'&search={{ request('search') }}'"
                        class="border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="">Tất cả trạng thái</option>
                        <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Nháp</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Chờ duyệt</option>
                        <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Đã duyệt</option>
                        <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Từ chối</option>
                        <option value="sent" {{ request('status') == 'sent' ? 'selected' : '' }}>Đã gửi khách</option>
                        <option value="accepted" {{ request('status') == 'accepted' ? 'selected' : '' }}>Khách chấp nhận
                        </option>
                        <option value="declined" {{ request('status') == 'declined' ? 'selected' : '' }}>Khách từ chối
                        </option>
                        <option value="converted" {{ request('status') == 'converted' ? 'selected' : '' }}>Đã chuyển ĐH
                        </option>
                    </select>
                </div>
            </div>

            <div class="flex gap-2">
                <a href="{{ route('quotations.export', request()->query()) }}"
                    class="inline-flex items-center px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-emerald-600 transition-colors">
                    <i class="fas fa-file-excel mr-2"></i>
                    Xuất Excel
                </a>
                <a href="{{ route('quotations.create') }}"
                    class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors">
                    <i class="fas fa-plus mr-2"></i>
                    Tạo báo giá
                </a>
            </div>
        </div>

        <!-- Info Box -->
        <div class="mx-4 mt-4 bg-blue-50 border-l-4 border-blue-500 p-3 rounded">
            <p class="text-blue-700 text-sm"><i class="fas fa-info-circle mr-2"></i>Quy trình: Nháp → Chờ duyệt → Đã duyệt →
                Gửi khách → Chấp nhận/Từ chối → Chuyển đơn hàng</p>
        </div>

        <!-- Table - Desktop View -->
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">STT
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mã báo
                            giá</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Khách
                            hàng</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tiêu đề
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ngày tạo
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hạn BG
                        </th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Tổng
                            tiền</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Trạng
                            thái</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Thao
                            tác</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($quotations as $quotation)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 whitespace-nowrap text-center text-sm text-gray-500">
                                {{ ($quotations->currentPage() - 1) * $quotations->perPage() + $loop->iteration }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <a href="{{ route('quotations.show', $quotation) }}"
                                    class="font-medium text-blue-600 hover:text-blue-800">
                                    {{ $quotation->code }}
                                </a>
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-sm font-medium text-gray-900">{{ $quotation->customer_name }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-sm text-gray-900 max-w-xs truncate">{{ $quotation->title }}</div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                {{ $quotation->date->format('d/m/Y') }}
                            </td>
                            <td
                                class="px-4 py-3 whitespace-nowrap text-sm {{ $quotation->isExpired() ? 'text-red-600 font-medium' : 'text-gray-500' }}">
                                {{ $quotation->valid_until->format('d/m/Y') }}
                                @if($quotation->isExpired())
                                    <i class="fas fa-exclamation-circle ml-1" title="Đã hết hạn"></i>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 text-right font-medium">
                                {{ number_format($quotation->total) }} đ
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-center">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $quotation->status_color }}">
                                    {{ $quotation->status_label }}
                                </span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="{{ route('quotations.show', $quotation) }}"
                                        class="p-2 text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 hover:text-blue-700 transition-colors"
                                        title="Xem chi tiết">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @if(in_array($quotation->status, ['draft', 'rejected']))
                                        <a href="{{ route('quotations.edit', $quotation) }}"
                                            class="p-2 text-yellow-600 bg-yellow-50 rounded-lg hover:bg-yellow-100 hover:text-yellow-700 transition-colors"
                                            title="Sửa">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('quotations.destroy', $quotation) }}" method="POST"
                                            onsubmit="return confirm('Bạn có chắc muốn xóa báo giá này?')" class="inline-block">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="p-2 text-red-600 bg-red-50 rounded-lg hover:bg-red-100 hover:text-red-700 transition-colors"
                                                title="Xóa">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                    <a href="{{ route('quotations.print', $quotation) }}" target="_blank"
                                        class="p-2 text-gray-600 bg-gray-50 rounded-lg hover:bg-gray-100 hover:text-gray-700 transition-colors"
                                        title="In">
                                        <i class="fas fa-print"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-8 text-center text-gray-500">
                                <i class="fas fa-file-alt text-4xl mb-2"></i>
                                <p>Chưa có báo giá nào</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Card View - Mobile -->
        <div class="md:hidden divide-y divide-gray-200">
            @forelse($quotations as $quotation)
                <div class="p-4 hover:bg-gray-50">
                    <div class="flex justify-between items-start mb-2">
                        <div class="flex-1">
                            <a href="{{ route('quotations.show', $quotation) }}"
                                class="font-medium text-blue-600 hover:text-blue-800">{{ $quotation->code }}</a>
                            <div class="text-sm text-gray-500">{{ $quotation->customer_name }}</div>
                        </div>
                        <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $quotation->status_color }}">
                            {{ $quotation->status_label }}
                        </span>
                    </div>
                    <div class="text-sm text-gray-700 mb-2 truncate">{{ $quotation->title }}</div>
                    <div class="space-y-1 text-sm text-gray-600 mb-3">
                        <div><i class="fas fa-calendar w-4"></i> {{ $quotation->date->format('d/m/Y') }} -
                            {{ $quotation->valid_until->format('d/m/Y') }}
                        </div>
                        <div><i class="fas fa-money-bill w-4"></i> {{ number_format($quotation->total) }} đ</div>
                    </div>
                    <div class="flex gap-2">
                        <a href="{{ route('quotations.show', $quotation) }}"
                            class="flex-1 text-center px-3 py-2 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 text-sm">
                            <i class="fas fa-eye mr-1"></i>Xem
                        </a>
                        @if(in_array($quotation->status, ['draft', 'rejected']))
                            <a href="{{ route('quotations.edit', $quotation) }}"
                                class="flex-1 text-center px-3 py-2 bg-yellow-100 text-yellow-700 rounded-lg hover:bg-yellow-200 text-sm">
                                <i class="fas fa-edit mr-1"></i>Sửa
                            </a>
                            <form action="{{ route('quotations.destroy', $quotation) }}" method="POST"
                                onsubmit="return confirm('Bạn có chắc muốn xóa báo giá này?')" class="flex-1">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class="w-full text-center px-3 py-2 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 text-sm">
                                    <i class="fas fa-trash mr-1"></i>Xóa
                                </button>
                            </form>
                        @endif
                        <a href="{{ route('quotations.print', $quotation) }}" target="_blank"
                            class="flex-1 text-center px-3 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm">
                            <i class="fas fa-print mr-1"></i>In
                        </a>
                    </div>
                </div>
            @empty
                <div class="p-8 text-center text-gray-500">
                    <i class="fas fa-file-alt text-4xl mb-2"></i>
                    <p>Chưa có báo giá nào</p>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        @if($quotations->hasPages())
            <div class="px-4 py-3 border-t border-gray-200">
                {{ $quotations->appends(request()->query())->links() }}
            </div>
        @endif
    </div>
@endsection