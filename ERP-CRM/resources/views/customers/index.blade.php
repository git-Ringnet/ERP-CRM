@extends('layouts.app')

@section('title', 'Khách hàng')
@section('page-title', 'Quản lý Khách hàng')

@section('content')
<div class="bg-white rounded-lg shadow-sm">
    <!-- Header -->
    <div class="p-3 sm:p-4 border-b border-gray-200 space-y-3">
        <div class="flex flex-col sm:flex-row gap-3">
            <!-- Search -->
            <div class="relative flex-1">
                <form action="{{ route('customers.index') }}" method="GET" class="flex">
                    <input type="text" name="search" value="{{ request('search') }}" 
                           placeholder="Tìm kiếm..." 
                           class="w-full pl-10 pr-4 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                    <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                </form>
            </div>
            
            <!-- Filter by Type -->
            <div class="flex items-center">
                <select name="type" onchange="window.location.href='{{ route('customers.index') }}?type='+this.value+'&search={{ request('search') }}'" 
                        class="w-full sm:w-auto border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="">Tất cả loại</option>
                    <option value="normal" {{ request('type') == 'normal' ? 'selected' : '' }}>Thường</option>
                    <option value="vip" {{ request('type') == 'vip' ? 'selected' : '' }}>VIP</option>
                </select>
            </div>
        </div>
        
        <div class="flex flex-col sm:flex-row gap-2">
            <a href="{{ route('customers.export') }}?{{ http_build_query(request()->query()) }}" 
               class="inline-flex items-center justify-center px-4 py-2 bg-success text-white rounded-lg hover:bg-green-600 transition-colors text-sm">
                <i class="fas fa-file-excel mr-2"></i>
                <span class="hidden sm:inline">Export Excel</span>
                <span class="sm:hidden">Export</span>
            </a>
            <a href="{{ route('customers.create') }}" 
               class="inline-flex items-center justify-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors text-sm">
                <i class="fas fa-plus mr-2"></i>
                <span class="hidden sm:inline">Thêm khách hàng</span>
                <span class="sm:hidden">Thêm</span>
            </a>
        </div>
    </div>

    <!-- Table - Desktop View -->
    <div class="hidden md:block overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">STT</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mã KH</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tên khách hàng</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Điện thoại</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Loại</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hạn mức nợ</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Thao tác</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($customers as $customer)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 whitespace-nowrap text-center text-sm text-gray-500">
                        {{ ($customers->currentPage() - 1) * $customers->perPage() + $loop->iteration }}
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        <span class="font-medium text-gray-900">{{ $customer->code }}</span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="text-sm font-medium text-gray-900">{{ $customer->name }}</div>
                        @if($customer->contact_person)
                            <div class="text-sm text-gray-500">LH: {{ $customer->contact_person }}</div>
                        @endif
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ $customer->email }}</td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ $customer->phone }}</td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        @if($customer->type == 'vip')
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                <i class="fas fa-crown mr-1"></i>VIP
                            </span>
                        @else
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                                Thường
                            </span>
                        @endif
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                        {{ number_format($customer->debt_limit) }} đ
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-center">
                        <div class="flex items-center justify-center gap-2">
                            <a href="{{ route('customers.show', $customer->id) }}" 
                               class="p-2 text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 hover:text-blue-700 transition-colors" 
                               title="Xem chi tiết">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('customers.edit', $customer->id) }}" 
                               class="p-2 text-yellow-600 bg-yellow-50 rounded-lg hover:bg-yellow-100 hover:text-yellow-700 transition-colors" 
                               title="Sửa">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('customers.destroy', $customer->id) }}" method="POST" class="inline delete-form">
                                @csrf
                                @method('DELETE')
                                <button type="submit" 
                                        class="p-2 text-red-600 bg-red-50 rounded-lg hover:bg-red-100 hover:text-red-700 transition-colors delete-btn" 
                                        data-name="{{ $customer->name }}" title="Xóa">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                        <i class="fas fa-inbox text-4xl mb-2"></i>
                        <p>Không có dữ liệu khách hàng</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Card View - Mobile -->
    <div class="md:hidden divide-y divide-gray-200">
        @forelse($customers as $customer)
        <div class="p-4 hover:bg-gray-50">
            <div class="flex justify-between items-start mb-2">
                <div class="flex-1">
                    <div class="font-medium text-gray-900">{{ $customer->name }}</div>
                    <div class="text-sm text-gray-500">{{ $customer->code }}</div>
                </div>
                @if($customer->type == 'vip')
                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                        <i class="fas fa-crown mr-1"></i>VIP
                    </span>
                @else
                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                        Thường
                    </span>
                @endif
            </div>
            <div class="space-y-1 text-sm text-gray-600 mb-3">
                <div><i class="fas fa-envelope w-4"></i> {{ $customer->email }}</div>
                <div><i class="fas fa-phone w-4"></i> {{ $customer->phone }}</div>
                @if($customer->contact_person)
                    <div><i class="fas fa-user w-4"></i> {{ $customer->contact_person }}</div>
                @endif
                <div><i class="fas fa-money-bill w-4"></i> {{ number_format($customer->debt_limit) }} đ</div>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('customers.show', $customer->id) }}" 
                   class="flex-1 text-center px-3 py-2 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 text-sm">
                    <i class="fas fa-eye mr-1"></i>Xem
                </a>
                <a href="{{ route('customers.edit', $customer->id) }}" 
                   class="flex-1 text-center px-3 py-2 bg-yellow-100 text-yellow-700 rounded-lg hover:bg-yellow-200 text-sm">
                    <i class="fas fa-edit mr-1"></i>Sửa
                </a>
                <form action="{{ route('customers.destroy', $customer->id) }}" method="POST" class="flex-1 delete-form">
                    @csrf
                    @method('DELETE')
                    <button type="submit" 
                            class="w-full px-3 py-2 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 text-sm delete-btn"
                            data-name="{{ $customer->name }}">
                        <i class="fas fa-trash mr-1"></i>Xóa
                    </button>
                </form>
            </div>
        </div>
        @empty
        <div class="p-8 text-center text-gray-500">
            <i class="fas fa-inbox text-4xl mb-2"></i>
            <p>Không có dữ liệu khách hàng</p>
        </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($customers->hasPages())
    <div class="px-4 py-3 border-t border-gray-200">
        {{ $customers->appends(request()->query())->links() }}
    </div>
    @endif
</div>
@endsection
