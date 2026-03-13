@extends('layouts.app')

@section('title', 'Danh mục Phụ cấp & Khấu trừ')
@section('page-title', 'Danh mục Phụ cấp & Khấu trừ')

@section('content')
<div class="bg-white rounded-lg shadow-sm">
    <!-- Header -->
    <div class="p-4 border-b border-gray-200 flex flex-wrap gap-4 items-center justify-between">
        <form action="{{ route('salary-components.index') }}" method="GET" class="flex gap-2 items-center flex-wrap">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Tìm kiếm tên khoản..." class="pl-3 pr-4 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary w-64">
            <select name="type" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                <option value="">Tất cả loại</option>
                <option value="allowance" {{ request('type') == 'allowance' ? 'selected' : '' }}>Phụ cấp (+)</option>
                <option value="deduction" {{ request('type') == 'deduction' ? 'selected' : '' }}>Khấu trừ (-)</option>
            </select>
            <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark text-sm whitespace-nowrap"><i class="fas fa-search mr-1"></i>Tìm</button>
            <a href="{{ route('salary-components.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 text-sm whitespace-nowrap"><i class="fas fa-redo"></i></a>
        </form>
        <a href="{{ route('salary-components.create') }}" class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors text-sm whitespace-nowrap">
            <i class="fas fa-plus mr-2"></i>Thêm mới
        </a>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-16">STT</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tên khoản</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Loại</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cách tính</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Mức mặc định</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Chịu thuế TNCN</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Thao tác</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($components as $component)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-center text-sm text-gray-500">{{ ($components->currentPage() - 1) * $components->perPage() + $loop->iteration }}</td>
                    <td class="px-4 py-3 font-medium text-gray-900">{{ $component->name }}</td>
                    <td class="px-4 py-3 text-sm">
                        @if($component->type == 'allowance')
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800"><i class="fas fa-plus-circle mr-1"></i>Phụ cấp</span>
                        @else
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800"><i class="fas fa-minus-circle mr-1"></i>Khấu trừ</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600">
                        {{ $component->amount_type == 'fixed' ? 'Số tiền cố định' : 'Phần trăm lương (%)' }}
                    </td>
                    <td class="px-4 py-3 text-right text-sm font-semibold {{ $component->type == 'allowance' ? 'text-green-600' : 'text-red-600' }}">
                        @if($component->amount_type == 'fixed')
                            {{ number_format($component->default_amount) }} đ
                        @else
                            {{ number_format($component->default_amount, 1) }} %
                        @endif
                    </td>
                    <td class="px-4 py-3 text-center">
                        @if($component->is_taxable)
                            <i class="fas fa-check-circle text-green-500"></i>
                        @else
                            <i class="fas fa-times-circle text-gray-300"></i>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-center">
                        <div class="flex items-center justify-center gap-2">
                            <a href="{{ route('salary-components.edit', $component->id) }}" class="p-2 text-yellow-600 bg-yellow-50 rounded-lg hover:bg-yellow-100 hover:text-yellow-700 transition-colors" title="Sửa">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('salary-components.destroy', $component->id) }}" method="POST" class="inline delete-form">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-2 text-red-600 bg-red-50 rounded-lg hover:bg-red-100 hover:text-red-700 transition-colors delete-btn" data-name="{{ $component->name }}" title="Xóa">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                        <i class="fas fa-list-ul text-4xl mb-2 text-gray-400"></i>
                        <p>Chưa có danh mục nào được tạo</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($components->hasPages())
    <div class="px-4 py-3 border-t border-gray-200">
        {{ $components->appends(request()->query())->links() }}
    </div>
    @endif
</div>
@endsection
