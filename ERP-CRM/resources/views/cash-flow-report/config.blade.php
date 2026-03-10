@extends('layouts.app')

@section('content')
<div class="p-6 h-full flex flex-col pt-20 transition-all duration-300">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold text-gray-800 flex items-center gap-3">
            <i class="fas fa-cogs text-gray-600"></i>
            Cấu hình Mẫu Báo cáo Dòng tiền
        </h1>
        <a href="{{ route('cash-flow-report.index') }}" class="text-primary hover:underline flex items-center gap-2">
            <i class="fas fa-arrow-left"></i> Quay lại báo cáo
        </a>
    </div>

    @if (session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
        {{ session('success') }}
    </div>
    @endif
    @if (session('error'))
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
        {{ session('error') }}
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 flex-1">
        <!-- Add Item Form -->
        <div class="lg:col-span-1 border rounded-lg shadow-sm bg-white self-start">
            <div class="border-b px-6 py-4 bg-gray-50 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-primary flex items-center gap-2">
                    <i class="fas fa-plus-circle"></i> Thêm hàng báo cáo mới
                </h2>
            </div>
            <div class="p-6">
                <form action="{{ route('cash-flow-report.config.store') }}" method="POST">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Mã lưu chuyển <span class="text-red-500">*</span></label>
                        <input type="text" name="code" value="{{ old('code') }}" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:border-primary @error('code') border-red-500 @enderror" placeholder="VD: 5">
                        <p class="text-xs text-gray-500 mt-1">Mã không được trùng lặp. (Dùng để nhóm các danh mục phụ)</p>
                        @error('code')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Tên hiển thị <span class="text-red-500">*</span></label>
                        <input type="text" name="name" value="{{ old('name') }}" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:border-primary @error('name') border-red-500 @enderror" placeholder="Tên hàng trên báo cáo">
                        @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Loại Dòng tiền <span class="text-red-500">*</span></label>
                        <select name="type" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:border-primary">
                            <option value="income" {{ old('type') == 'income' ? 'selected' : '' }}>Thu (Dòng tiền vào)</option>
                            <option value="expense" {{ old('type') == 'expense' ? 'selected' : '' }}>Chi (Dòng tiền ra)</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Thứ tự hiển thị <span class="text-red-500">*</span></label>
                        <input type="number" name="sort_order" value="{{ old('sort_order', 10) }}" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:border-primary">
                    </div>

                    <button type="submit" class="bg-primary hover:bg-blue-600 text-white font-bold py-2 px-4 rounded w-full transition-colors flex items-center justify-center gap-2">
                        <i class="fas fa-save"></i> Lưu hàng báo cáo
                    </button>
                </form>
            </div>
        </div>

        <!-- Items Table -->
        <div class="lg:col-span-2 shadow-sm rounded-lg bg-white border self-start">
            <div class="border-b px-6 py-4 bg-gray-50">
                <h2 class="text-lg font-semibold text-gray-800">Danh sách Cấu hình Báo cáo</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full whitespace-nowrap">
                    <thead class="bg-gray-50 text-gray-500 text-xs font-semibold uppercase tracking-wider text-left border-b">
                        <tr>
                            <th class="px-6 py-4">Mã</th>
                            <th class="px-6 py-4">Tên hiển thị trên báo cáo</th>
                            <th class="px-6 py-4 text-center">Loại</th>
                            <th class="px-6 py-4 text-center">Thứ tự</th>
                            <th class="px-6 py-4 text-center">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($items as $item)
                        <tr class="hover:bg-blue-50 transition-colors duration-150">
                            <td class="px-6 py-4">
                                <span class="bg-gray-100 font-mono text-gray-600 py-1 px-2 rounded">{{ $item->code }}</span>
                            </td>
                            <td class="px-6 py-4 font-medium text-gray-800">
                                {{ $item->name }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($item->type === 'income')
                                    <span class="bg-green-100 text-green-700 text-xs px-2 py-1 rounded-full"><i class="fas fa-arrow-down mr-1"></i> Thu</span>
                                @else
                                    <span class="bg-red-100 text-red-700 text-xs px-2 py-1 rounded-full"><i class="fas fa-arrow-up mr-1"></i> Chi</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center text-gray-500">
                                {{ $item->sort_order }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                <form action="{{ route('cash-flow-report.config.destroy', $item) }}" method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn xóa hạng mục này? Lưu ý: Nếu có danh mục thực tế đang gắn với mã này thì báo lỗi.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:text-red-700 p-2 hover:bg-red-50 rounded-full transition-colors" title="Xóa">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                        
                        @if($items->isEmpty())
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-gray-500 italic">
                                Chưa có hàng báo cáo nào được cấu hình.
                            </td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
