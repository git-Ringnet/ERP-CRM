@extends('layouts.app')

@section('title', 'Thêm kỹ năng')
@section('page-title', 'Thêm kỹ năng mới')

@section('content')
<div class="space-y-6">
    <a href="{{ route('skills.index') }}" 
       class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
        <i class="fas fa-arrow-left mr-2"></i>Quay lại
    </a>

    <div class="bg-white rounded-lg shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-plus-circle mr-2 text-primary"></i>Thêm kỹ năng mới
            </h2>
        </div>
        <div class="p-6">
            <form action="{{ route('skills.store') }}" method="POST" class="space-y-5">
                @csrf

                {{-- Chọn danh mục --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Danh mục <span class="text-red-500">*</span></label>
                    <select name="skill_category_id" id="skill_category_id"
                            class="w-full border-gray-300 rounded-lg shadow-sm focus:border-primary focus:ring focus:ring-primary/20">
                        <option value="">-- Chọn danh mục --</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ old('skill_category_id') == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('skill_category_id')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Tạo danh mục mới --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Hoặc tạo danh mục mới
                    </label>
                    <input type="text" name="new_category" value="{{ old('new_category') }}"
                           class="w-full border-gray-300 rounded-lg shadow-sm focus:border-primary focus:ring focus:ring-primary/20"
                           placeholder="Nhập tên danh mục mới (VD: Kỹ thuật, Ngoại ngữ...)">
                    <p class="text-xs text-gray-400 mt-1">Nếu nhập ở đây, hệ thống sẽ ưu tiên tạo danh mục mới.</p>
                </div>

                <hr class="border-gray-200">

                {{-- Tên kỹ năng --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tên kỹ năng <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                           class="w-full border-gray-300 rounded-lg shadow-sm focus:border-primary focus:ring focus:ring-primary/20"
                           placeholder="VD: Excel, Giao tiếp, Tiếng Anh...">
                    @error('name')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Mô tả --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mô tả</label>
                    <textarea name="description" rows="3"
                              class="w-full border-gray-300 rounded-lg shadow-sm focus:border-primary focus:ring focus:ring-primary/20"
                              placeholder="Mô tả ngắn về kỹ năng này (tùy chọn)">{{ old('description') }}</textarea>
                </div>

                <div class="flex justify-end gap-3">
                    <a href="{{ route('skills.index') }}" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                        Hủy
                    </a>
                    <button type="submit" class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors">
                        <i class="fas fa-save mr-2"></i>Lưu
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
