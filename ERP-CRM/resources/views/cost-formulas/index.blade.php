@extends('layouts.app')

@section('title', 'Công thức chi phí')
@section('page-title', 'Quản lý Công thức chi phí')

@section('content')
<div class="bg-white rounded-lg shadow-sm">
    <!-- Header -->
    <div class="p-4 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex flex-col sm:flex-row gap-4 flex-1">
            <!-- Search -->
            <div class="relative flex-1 max-w-md">
                <form action="{{ route('cost-formulas.index') }}" method="GET" class="flex">
                    <input type="text" name="search" value="{{ request('search') }}" 
                           placeholder="Tìm kiếm công thức..." 
                           class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                    <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                </form>
            </div>
            
            <!-- Filter by Type -->
            <div class="flex items-center gap-2">
                <select name="type" onchange="window.location.href='{{ route('cost-formulas.index') }}?type='+this.value+'&search={{ request('search') }}'" 
                        class="border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="">Tất cả loại</option>
                    <option value="shipping" {{ request('type') == 'shipping' ? 'selected' : '' }}>Vận chuyển</option>
                    <option value="marketing" {{ request('type') == 'marketing' ? 'selected' : '' }}>Marketing</option>
                    <option value="commission" {{ request('type') == 'commission' ? 'selected' : '' }}>Hoa hồng</option>
                    <option value="other" {{ request('type') == 'other' ? 'selected' : '' }}>Khác</option>
                </select>
            </div>
        </div>
        
        <div class="flex gap-2">
            <a href="{{ route('cost-formulas.create') }}" 
               class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors">
                <i class="fas fa-plus mr-2"></i>
                Thêm công thức
            </a>
        </div>
    </div>

    <!-- Info Box -->
    <div class="p-4 bg-blue-50 border-b border-blue-100">
        <div class="flex items-start gap-3">
            <i class="fas fa-info-circle text-blue-600 mt-1"></i>
            <div class="text-sm text-blue-800">
                <strong>Công thức chi phí bán hàng:</strong> Thiết lập công thức tự động tính chi phí (vận chuyển, hoa hồng, marketing...) theo sản phẩm, khách hàng hoặc điều kiện khác. Khi tạo đơn hàng, chi phí sẽ được tính tự động.
            </div>
        </div>
    </div>

    <!-- Table - Desktop View -->
    <div class="hidden md:block overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">STT</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mã CT</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tên công thức</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Loại chi phí</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cách tính</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Áp dụng cho</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Trạng thái</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Thao tác</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($formulas as $formula)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 whitespace-nowrap text-center text-sm text-gray-500">
                        {{ ($formulas->currentPage() - 1) * $formulas->perPage() + $loop->iteration }}
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        <span class="font-medium text-gray-900">{{ $formula->code }}</span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="text-sm font-medium text-gray-900">{{ $formula->name }}</div>
                        @if($formula->description)
                            <div class="text-xs text-gray-500">{{ Str::limit($formula->description, 50) }}</div>
                        @endif
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full 
                            {{ $formula->type == 'shipping' ? 'bg-blue-100 text-blue-800' : '' }}
                            {{ $formula->type == 'marketing' ? 'bg-orange-100 text-orange-800' : '' }}
                            {{ $formula->type == 'commission' ? 'bg-green-100 text-green-800' : '' }}
                            {{ $formula->type == 'other' ? 'bg-gray-100 text-gray-800' : '' }}">
                            {{ $formula->type_label }}
                        </span>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm">
                        <div class="font-medium text-gray-900">{{ $formula->calculation_type_label }}</div>
                        @if($formula->calculation_type == 'fixed')
                            <div class="text-xs text-gray-500">{{ number_format($formula->fixed_amount) }} đ</div>
                        @elseif($formula->calculation_type == 'percentage')
                            <div class="text-xs text-gray-500">{{ $formula->percentage }}%</div>
                        @else
                            <div class="text-xs text-gray-500 font-mono">{{ Str::limit($formula->formula, 30) }}</div>
                        @endif
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                        @if($formula->apply_to == 'all')
                            <span class="text-blue-600">Tất cả</span>
                        @elseif($formula->apply_to == 'product')
                            <span class="text-purple-600">Theo sản phẩm</span>
                        @elseif($formula->apply_to == 'customer')
                            <span class="text-green-600">Theo khách hàng</span>
                        @else
                            <span>{{ ucfirst($formula->apply_to) }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-center">
                        @if($formula->is_active)
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                Hoạt động
                            </span>
                        @else
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                                Tạm dừng
                            </span>
                        @endif
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-center">
                        <div class="flex items-center justify-center gap-2">
                            <a href="{{ route('cost-formulas.edit', $formula->id) }}" 
                               class="text-yellow-600 hover:text-yellow-900" title="Sửa">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('cost-formulas.destroy', $formula->id) }}" method="POST" class="inline delete-form">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900 delete-btn" 
                                        data-name="{{ $formula->name }}" title="Xóa">
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
                        <p>Chưa có công thức chi phí nào</p>
                        <a href="{{ route('cost-formulas.create') }}" class="text-primary hover:underline mt-2 inline-block">
                            Tạo công thức đầu tiên
                        </a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($formulas->hasPages())
    <div class="px-4 py-3 border-t border-gray-200">
        {{ $formulas->appends(request()->query())->links() }}
    </div>
    @endif
</div>
@endsection
